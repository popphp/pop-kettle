<?php
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
use Pop\Kettle\Exception;

/**
 * Console controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@noladev.com>
 * @copyright  Copyright (c) 2012-2025 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.3.3
 */
class ConsoleController extends AbstractController
{

    /**
     * Serve command
     *
     * @param  array $options
     * @param  bool  $test
     * @throws Exception
     * @return void
     */
    public function serve(array $options = [], bool $test = false): void
    {
        if (!function_exists('exec')) {
            throw new Exception("Error: The `exec()` function is not available. It is required to run PHP's web server.");
        }

        $host   = (isset($options['host']))   ? $options['host']   : 'localhost';
        $port   = (isset($options['port']))   ? $options['port']   : 8000;
        $folder = (isset($options['folder'])) ? $options['folder'] : 'public';

        $this->console->write(
            'PHP web server running on the folder ' . $this->console->colorize($folder, Color::BOLD_YELLOW) .' at ' .
            $this->console->colorize($host . ':' . $port, Color::BOLD_GREEN) . '... (Ctrl-C to stop)'
        );
        $this->console->write();

        if (!$test) {
            exec('php -S ' . $host . ':' . $port . ' -t ' . $folder);
        }
    }

    /**
     * Help command
     *
     * @param  array $options
     * @return void
     */
    public function help(array $options = []): void
    {
        $raw = isset($options['raw']);
        $this->console->help(null, $raw);
    }

    /**
     * Version command
     *
     * @return void
     */
    public function version(): void
    {
        $this->console->write('Version: ' . $this->console->colorize(\Pop\Kettle\Module::VERSION, Color::BOLD_GREEN));
    }

}
