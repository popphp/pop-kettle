<?php

namespace Pop\Kettle\Test\Fixtures;

use Pop\Db\Db;
use Pop\Dir\Dir;
use Pop\Kettle;

/**
 * Shared test helpers for building a Kettle application instance,
 * feeding canned console input and running filesystem-touching code
 * inside an isolated sandbox directory instead of the real project root.
 */
trait AppTestTrait
{

    /**
     * Currently active sandbox directory, if any
     * @var ?string
     */
    protected ?string $sandboxDir = null;

    /**
     * Working directory to restore after leaving the sandbox
     * @var ?string
     */
    protected ?string $sandboxOrigCwd = null;

    /**
     * Build a fresh Pop\Kettle\Application instance the same way the real
     * `kettle` front-controller script does.
     *
     * @param  ?string $rootDir Base dir the vendor/autoload.php and config live under (defaults to repo root)
     * @return Kettle\Application
     */
    protected function makeApp(?string $rootDir = null): Kettle\Application
    {
        $rootDir = $rootDir ?? (__DIR__ . '/../..');

        return new Kettle\Application(
            include $rootDir . '/vendor/autoload.php',
            include $rootDir . '/config/app.console.php'
        );
    }

    /**
     * Create an in-memory stream pre-loaded with lines of canned input,
     * suitable for Console::setInputStream().
     *
     * @param  string ...$lines
     * @return resource
     */
    protected function createInputStream(string ...$lines): mixed
    {
        $stream = fopen('php://memory', 'r+');
        foreach ($lines as $line) {
            fwrite($stream, $line . PHP_EOL);
        }
        rewind($stream);
        return $stream;
    }

    /**
     * Create an isolated, empty directory and chdir into it, so that code
     * under test that relies on getcwd() (scaffolding, migrations, seeds,
     * .env writes, etc.) doesn't read or write the real project root.
     *
     * @return string The sandbox directory path
     */
    protected function enterSandbox(): string
    {
        $this->sandboxOrigCwd = getcwd();
        $this->sandboxDir     = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pop-kettle-test-' . uniqid();

        mkdir($this->sandboxDir, 0777, true);
        chdir($this->sandboxDir);

        return $this->sandboxDir;
    }

    /**
     * Restore the original working directory and remove the sandbox.
     *
     * @return void
     */
    protected function leaveSandbox(): void
    {
        if ($this->sandboxOrigCwd !== null) {
            chdir($this->sandboxOrigCwd);
            $this->sandboxOrigCwd = null;
        }

        if (($this->sandboxDir !== null) && file_exists($this->sandboxDir)) {
            (new Dir($this->sandboxDir))->emptyDir(true);
        }

        $this->sandboxDir = null;
    }

    /**
     * Determine the 1-based prompt index of the sqlite adapter, matching the
     * numbering Model\Database::configure() prints, so tests can drive the
     * interactive database-adapter prompt without hardcoding an index that
     * would break if the available adapters on a given machine differ.
     *
     * @return int
     */
    protected function sqliteAdapterIndex(): int
    {
        $index = 0;
        $i     = 0;

        foreach (Db::getAvailableAdapters() as $adapter => $result) {
            if ($adapter == 'pdo') {
                foreach ($result as $r) {
                    if ($r) {
                        $i++;
                    }
                }
            } else if ($result) {
                $i++;
                if (strtolower($adapter) == 'sqlite') {
                    $index = $i;
                    break;
                }
            }
        }

        return $index;
    }

    /**
     * Determine the 1-based prompt index of the direct (non-PDO) mysqli
     * adapter, matching the numbering Model\Database::configure() prints,
     * so tests can drive the interactive database-adapter prompt without
     * hardcoding an index that would break if the available adapters on a
     * given machine differ.
     *
     * @return int
     */
    protected function mysqlAdapterIndex(): int
    {
        $index = 0;
        $i     = 0;

        foreach (Db::getAvailableAdapters() as $adapter => $result) {
            if ($adapter == 'pdo') {
                foreach ($result as $r) {
                    if ($r) {
                        $i++;
                    }
                }
            } else if ($result) {
                $i++;
                if (strtolower($adapter) == 'mysqli') {
                    $index = $i;
                    break;
                }
            }
        }

        return $index;
    }

    /**
     * Seed the sandbox with a minimal composer.json, so install() has
     * something to add the app's PSR-4 autoload entry to.
     *
     * @return void
     */
    protected function seedComposerJson(): void
    {
        file_put_contents(getcwd() . '/composer.json', json_encode(['name' => 'test/app'], JSON_PRETTY_PRINT) . PHP_EOL);
    }

    /**
     * Scaffold an application into the sandbox (current working
     * directory), so controller/model tests that generate additional files
     * (controllers, models, views, commands) have real target folders and a
     * detectable namespace to work against.
     *
     * @param  bool   $cliOnly Whether to scaffold the lean CLI-only tree instead of the full web+api+cli tree
     * @param  string $namespace
     * @param  bool   $cliApp Whether to also scaffold the stand-alone ./script console application
     * @return void
     */
    protected function scaffoldApp(bool $cliOnly = false, string $namespace = 'App', bool $cliApp = false): void
    {
        (new Kettle\Model\Application())->install($cliOnly, getcwd(), $namespace, cliApp: $cliApp);
    }

}
