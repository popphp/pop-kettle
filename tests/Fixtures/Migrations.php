<?php

namespace Pop\Kettle\Test\Fixtures;

use Pop\Db\Record;

/**
 * Migration state table fixture.
 *
 * Placing this class name in a '.table' file inside a migrations folder
 * switches Pop\Db\Sql\Migrator over from file-based state storage (a
 * '.current' file) to table-based state storage, which is what tracks
 * migration batch numbers.
 */
class Migrations extends Record
{

    /**
     * Table name
     * @var ?string
     */
    protected ?string $table = 'migrations';

}
