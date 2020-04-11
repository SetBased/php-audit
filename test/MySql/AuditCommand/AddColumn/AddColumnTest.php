<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\AddColumn;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\Middle\Helper\RowSetHelper;

/**
 * Tests for running audit with a new table column.
 */
class AddColumnTest extends AuditCommandTestCase
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
    $tables = AuditDataLayer::$dl->getTablesNames(self::$auditSchema);
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE1'));

    // TABLE1 MUST have triggers.
    $triggers = AuditDataLayer::$dl->getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_insert'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_update'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_delete'));

    $actual = AuditDataLayer::$dl->getTableColumns(self::$auditSchema, 'TABLE1');

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
    $expected[] = ['column_name'        => 'c4',
                   'column_type'        => 'int(11)',
                   'column_default'     => 'NULL',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];

    self::assertSame($expected, $actual);

    // Create new column.
    AuditDataLayer::$dl->executeMulti(file_get_contents(__DIR__.'/config/create_new_column.sql'));

    $this->runAudit();

    // TABLE1 MUST exist.
    $tables = AuditDataLayer::$dl->getTablesNames(self::$auditSchema);
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE1'));

    // TABLE1 MUST have triggers.
    $triggers = AuditDataLayer::$dl->getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_insert'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_update'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_delete'));

    // TABLE1 must have column c3.
    $actual = AuditDataLayer::$dl->getTableColumns(self::$auditSchema, 'TABLE1');

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
    AuditDataLayer::$dl->executeNone('insert into `TABLE1`(c1, c2, c3, c4) values(1,  2, 3, 4)');
    AuditDataLayer::$dl->executeNone('update `TABLE1` set c1=10, c2=20, c3=30, c4=40');
    AuditDataLayer::$dl->executeNone('delete from `TABLE1`');

    $rows = AuditDataLayer::$dl->executeRows(sprintf('select * from `%s`.`TABLE1` where c3 is not null',
                                                     self::$auditSchema));
    self::assertSame(4, count($rows), 'row_count');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
