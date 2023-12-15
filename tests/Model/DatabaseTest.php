<?php

namespace Pop\Kettle\Test\Model;

use Pop\Kettle\Model;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{


    public function testInit()
    {
        $database = new Model\Database();
        $this->assertInstanceOf('Pop\Kettle\Model\Database', $database);
    }

}
