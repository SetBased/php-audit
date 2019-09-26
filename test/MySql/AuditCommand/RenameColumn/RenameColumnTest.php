<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\RenameColumn;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\Middle\Helper\RowSetHelper;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests for running audit with a renamed column.
 */
class RenameColumnTest extends AuditCommandTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public static function setUpBeforeClass(): void
  {
    self::$dir = __DIR__;

    parent::setUpBeforeClass();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Run audit on a table with a renamed column.
   */
  public function test01(): void
  {
    // Run audit.
    $this->runAudit();

    // Insert a row into TABLE1.
    StaticDataLayer::executeNone('insert into `TABLE1`(c1, c2, c3, c4) values(1, 2, 3, 4)');

    // Rename column c3 to d3.
    StaticDataLayer::executeMulti(file_get_contents(__DIR__.'/config/rename_column.sql'));

    // We expect exit status 0.
    $this->runAudit(0);

    $columns = AuditDataLayer::getTableColumns(self::$auditSchema, 'TABLE1');

    // Assert column c3 still exists.
    self::assertNotNull(RowSetHelper::searchInRowSet($columns, 'column_name', 'c3'));

    // Assert column d3 exists.
    self::assertNotNull(RowSetHelper::searchInRowSet($columns, 'column_name', 'd3'));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
