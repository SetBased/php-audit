<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\AddTimestampColumn;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\Middle\Helper\RowSetHelper;

/**
 * Tests for running audit with a new table column.
 */
class AddColumnTimestampTest extends AuditCommandTestCase
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

    $expected = [['column_name'        => 'audit_timestamp',
                  'column_type'        => 'timestamp',
                  'column_default'     => 'current_timestamp()',
                  'is_nullable'        => 'NO',
                  'character_set_name' => null,
                  'collation_name'     => null],
                 ['column_name'        => 'audit_statement',
                  'column_type'        => "enum('INSERT','DELETE','UPDATE')",
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'NO',
                  'character_set_name' => 'ascii',
                  'collation_name'     => 'ascii_general_ci'],
                 ['column_name'        => 'audit_type',
                  'column_type'        => "enum('OLD','NEW')",
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'NO',
                  'character_set_name' => 'ascii',
                  'collation_name'     => 'ascii_general_ci'],
                 ['column_name'        => 'c1',
                  'column_type'        => 'tinyint(4)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => null,
                  'collation_name'     => null],
                 ['column_name'        => 'c2',
                  'column_type'        => 'smallint(6)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => null,
                  'collation_name'     => null],
                 ['column_name'        => 'c5',
                  'column_type'        => 'int(11)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => null,
                  'collation_name'     => null]];

    self::assertSame($expected, $actual);

    // Create new columns.
    AuditDataLayer::$dl->executeMulti(file_get_contents(__DIR__.'/config/create_new_columns.sql'));

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

    $expected = [['column_name'        => 'audit_timestamp',
                  'column_type'        => 'timestamp',
                  'column_default'     => 'current_timestamp()',
                  'is_nullable'        => 'NO',
                  'character_set_name' => null,
                  'collation_name'     => null],
                 ['column_name'        => 'audit_statement',
                  'column_type'        => "enum('INSERT','DELETE','UPDATE')",
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'NO',
                  'character_set_name' => 'ascii',
                  'collation_name'     => 'ascii_general_ci'],
                 ['column_name'        => 'audit_type',
                  'column_type'        => "enum('OLD','NEW')",
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'NO',
                  'character_set_name' => 'ascii',
                  'collation_name'     => 'ascii_general_ci'],
                 ['column_name'        => 'c1',
                  'column_type'        => 'tinyint(4)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => null,
                  'collation_name'     => null],
                 ['column_name'        => 'c2',
                  'column_type'        => 'smallint(6)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => null,
                  'collation_name'     => null],
                 ['column_name'        => 'c3',
                  'column_type'        => 'timestamp',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => null,
                  'collation_name'     => null],
                 ['column_name'        => 'c4',
                  'column_type'        => 'timestamp',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => null,
                  'collation_name'     => null],
                 ['column_name'        => 'c5',
                  'column_type'        => 'int(11)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => null,
                  'collation_name'     => null]];

    self::assertSame($expected, $actual);

    // Test triggers.
    AuditDataLayer::$dl->executeNone('insert into `TABLE1`(c1, c2, c4, c5) values(1, 2, \'2000-01-01\', 5)');
    AuditDataLayer::$dl->executeNone('update `TABLE1` set c1=10, c2=20, c4=\'2020-01-01\', c5=50');

    $rows = AuditDataLayer::$dl->executeRows(sprintf('select * from `%s`.`TABLE1`',
                                                     self::$dataSchema));
    AuditDataLayer::$dl->executeNone('delete from `TABLE1`');

    $rows = AuditDataLayer::$dl->executeRows(sprintf('select * from `%s`.`TABLE1` where c5 is not null',
                                                     self::$auditSchema));
    self::assertSame(4, count($rows), 'row_count');

    $rows = AuditDataLayer::$dl->executeRows(sprintf('select * from `%s`.`TABLE1` where c3 is null',
                                                     self::$auditSchema));
    self::assertSame(0, count($rows), 'row_count');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
