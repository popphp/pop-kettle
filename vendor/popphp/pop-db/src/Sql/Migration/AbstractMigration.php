<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Migration;

/**
 * Db SQL migration abstract class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.4.0
 */
abstract class AbstractMigration extends AbstractMigrator implements MigrationInterface
{

    /**
     * Execute an UP migration (new forward changes)
     *
     * @return MigrationInterface
     */
    abstract public function up();

    /**
     * Execute a DOWN migration (rollback previous changes)
     *
     * @return MigrationInterface
     */
    abstract public function down();

}