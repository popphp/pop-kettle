<?php

namespace Pop\Kettle\Test;

use Pop\Application;
use Pop\Kettle;
use PHPUnit\Framework\TestCase;

class KettleTest extends TestCase
{

    public function testRegister()
    {
        $app = new Application(include __DIR__ . '/../vendor/autoload.php', include __DIR__ . '/../config/app.console.php');
        if (file_exists(__DIR__ . '/../kettle.inc.php')) {
            include __DIR__ . '/../kettle.inc.php';
        }
        $app->register(new Kettle\Module());
        $app->modules['pop-kettle']->initDb(include __DIR__ . '/tmp/app/config/database.php');
        $this->assertInstanceOf('Pop\Application', $app);
    }

    public function testBadDb()
    {
        $this->expectException('Pop\Db\Adapter\Exception');
        $app = new Application(include __DIR__ . '/../vendor/autoload.php', include __DIR__ . '/../config/app.console.php');
        if (file_exists(__DIR__ . '/../kettle.inc.php')) {
            include __DIR__ . '/../kettle.inc.php';
        }
        $app->register(new Kettle\Module());
        $app->modules['pop-kettle']->initDb(include __DIR__ . '/tmp/app/config/database-bad.php');
    }

    public function testCliError()
    {
        $app = new Application(include __DIR__ . '/../vendor/autoload.php', include __DIR__ . '/../config/app.console.php');
        if (file_exists(__DIR__ . '/../kettle.inc.php')) {
            include __DIR__ . '/../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        ob_start();
        $app->modules['pop-kettle']->cliError(new Kettle\Exception('This was an error.'), false);
        $result = ob_get_clean();

        $this->assertStringContainsString('This was an error.', $result);
    }

}
