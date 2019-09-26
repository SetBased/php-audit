<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\DropColumn;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\Middle\Helper\RowSetHelper;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests for running audit with a new table column.
 */
class DropColumnTest extends AuditCommandTestCase
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
  public function test01(): void
  {
    // Run audit.
    $this->runAudit();

    // TABLE1 MUST exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE1'));

    // TABLE1 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_insert'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_update'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_delete'));

    $actual = AuditDataLayer::getTableColumns(self::$auditSchema, 'TABLE1');

    $expected   = [];
    $expected[] = ['column_name'        => 'c1',
                   'column_type'        => 'tinyint(4)',
                   'column_default'     => 'NULL',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];
    $expected[] = ['column_name'        => 'c2',
                   'column_type'        => 'smallint(6)',
                   'column_default'     => 'NULL',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];
    $expected[] = ['column_name'        => 'c3',
                   'column_type'        => 'mediumint(9)',
                   'column_default'     => 'NULL',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];
    $expected[] = ['column_name'        => 'c4',
                   'column_type'        => 'int(11)',
                   'column_default'     => 'NULL',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];

    self::assertSame($expected, $actual);

    // Test triggers.
    StaticDataLayer::executeNone('insert into `TABLE1`(c1, c2, c3, c4) values(1, 2, 3, 4)');
    StaticDataLayer::executeNone('update `TABLE1` set c1=10, c2=20, c3=30, c4=40');
    StaticDataLayer::executeNone('delete from `TABLE1`');

    $rows = StaticDataLayer::executeRows(sprintf('select * from `%s`.`TABLE1` where c3 is not null',
                                                 self::$auditSchema));
    self::assertSame(4, count($rows), 'row_count1');

    // Drop column c3.
    StaticDataLayer::executeMulti(file_get_contents(__DIR__.'/config/drop_column.sql'));

    $this->runAudit();

    // TABLE1 MUST exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE1'));

    // TABLE1 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_insert'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_update'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_delete'));

    // TABLE1 must have column c3.
    $actual = AuditDataLayer::getTableColumns(self::$auditSchema, 'TABLE1');

    $expected   = [];
    $expected[] = ['column_name'        => 'c1',
                   'column_type'        => 'tinyint(4)',
                   'column_default'     => 'NULL',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];
    $expected[] = ['column_name'        => 'c2',
                   'column_type'        => 'smallint(6)',
                   'column_default'     => 'NULL',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];
    $expected[] = ['column_name'        => 'c3',
                   'column_type'        => 'mediumint(9)',
                   'column_default'     => 'NULL',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];
    $expected[] = ['column_name'        => 'c4',
                   'column_type'        => 'int(11)',
                   'column_default'     => 'NULL',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];

    self::assertSame($expected, $actual);

    // Test triggers.
    StaticDataLayer::executeNone('insert into `TABLE1`(c1, c2, c4) values(1, 2, 4)');
    StaticDataLayer::executeNone('update `TABLE1` set c1=10, c2=20, c4=40');
    StaticDataLayer::executeNone('delete from `TABLE1`');

    // Assert we 4 rows with c3 is null.
    $rows = StaticDataLayer::executeRows(sprintf('select * from `%s`.`TABLE1` where c3 is null',
                                                 self::$auditSchema));
    self::assertSame(4, count($rows), 'row_count2');

    // Assert we 8 rows in total.
    $rows = StaticDataLayer::executeRows(sprintf('select * from `%s`.`TABLE1`', self::$auditSchema));
    self::assertSame(8, count($rows), 'row_count3');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
