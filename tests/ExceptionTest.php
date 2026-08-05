<?php

namespace Pop\Kettle\Test;

use Pop\Kettle\Exception;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{

    public function testThrow()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('This was an error.');

        throw new Exception('This was an error.');
    }

}
