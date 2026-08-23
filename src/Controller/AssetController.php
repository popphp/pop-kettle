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
namespace Pop\Kettle\Controller;

use Pop\Kettle\Model;

/**
 * Console asset controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class AssetController extends AbstractController
{

    /**
     * Watch command
     *
     * @return void
     */
    public function watch(): void
    {
        $this->runAssetCommand('watch');
    }

    /**
     * Build command
     *
     * @return void
     */
    public function build(): void
    {
        $this->runAssetCommand('build');
    }

    /**
     * Run an asset command (watch/build) after validating the project state
     *
     * @param  string $action
     * @return void
     */
    protected function runAssetCommand(string $action): void
    {
        $location = getcwd();
        $asset    = new Model\Asset();

        if (!$asset->isInstalled($location)) {
            $this->console->write('No front-end has been installed for this project.');
            return;
        }
        if (!$asset->isNpmAvailable()) {
            $this->console->write('Node/npm was not found on your PATH.');
            return;
        }

        match ($action) {
            'watch' => $asset->watch($location),
            'build' => $asset->build($location),
            default => null,
        };
    }

}
