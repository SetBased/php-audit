<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\AddAuditColumn;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\Middle\Helper\RowSetHelper;

/**
 * Tests for running audit with a new audit table column.
 */
class AddAuditColumnTest extends AuditCommandTestCase
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
    $replace = ['tinyint' => 'tinyint(4)'];

    // Run audit.
    copy(__DIR__.'/config/audit1.json', __DIR__.'/config/audit.json');
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

    foreach ($actual as $key => $row)
    {
      // Fix for MariaDB 10.6+.
      if ($row['character_set_name']!==null)
      {
        $actual[$key]['character_set_name'] = str_replace('utf8mb3', 'utf8', $row['character_set_name']);
      }

      // Fix for MariaDB 10.6+.
      if ($row['collation_name']!==null)
      {
        $actual[$key]['collation_name'] = str_replace('utf8mb3', 'utf8', $row['collation_name']);
      }

      // Fix for MySQL 8.x.
      foreach ($replace as $from => $to)
      {
        if ($row['column_type']===$from)
        {
          $actual[$key]['column_type'] = $to;
        }
      }
    }

    $expected = [['column_name'        => 'audit_column1',
                  'column_type'        => 'varchar(21)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => 'utf8',
                  'collation_name'     => 'utf8_general_ci'],
                 ['column_name'        => 'audit_column3',
                  'column_type'        => 'varchar(23)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => 'utf8',
                  'collation_name'     => 'utf8_general_ci'],
                 ['column_name'        => 'c1',
                  'column_type'        => 'tinyint(4)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => null,
                  'collation_name'     => null]];

    self::assertSame($expected, $actual);

    copy(__DIR__.'/config/audit2.json', __DIR__.'/config/audit.json');
    $this->runAudit();

    // TABLE1 MUST exist.
    $tables = AuditDataLayer::$dl->getTablesNames(self::$auditSchema);
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE1'));

    // TABLE1 MUST have triggers.
    $triggers = AuditDataLayer::$dl->getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_insert'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_update'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_delete'));

    // TABLE1 must have column audit_column2 between audit_column1 and audit_column3.
    $actual = AuditDataLayer::$dl->getTableColumns(self::$auditSchema, 'TABLE1');

    foreach ($actual as $key => $row)
    {
      // Fix for MariaDB 10.6+.
      if ($row['character_set_name']!==null)
      {
        $actual[$key]['character_set_name'] = str_replace('utf8mb3', 'utf8', $row['character_set_name']);
      }

      // Fix for MariaDB 10.6+.
      if ($row['collation_name']!==null)
      {
        $actual[$key]['collation_name'] = str_replace('utf8mb3', 'utf8', $row['collation_name']);
      }

      // Fix for MySQL 8.x.
      foreach ($replace as $from => $to)
      {
        if ($row['column_type']===$from)
        {
          $actual[$key]['column_type'] = $to;
        }
      }
    }

    $expected = [['column_name'        => 'audit_column1',
                  'column_type'        => 'varchar(21)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => 'utf8',
                  'collation_name'     => 'utf8_general_ci'],
                 ['column_name'        => 'audit_column2',
                  'column_type'        => 'varchar(22)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => 'utf8',
                  'collation_name'     => 'utf8_general_ci'],
                 ['column_name'        => 'audit_column3',
                  'column_type'        => 'varchar(23)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => 'utf8',
                  'collation_name'     => 'utf8_general_ci'],
                 ['column_name'        => 'c1',
                  'column_type'        => 'tinyint(4)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => null,
                  'collation_name'     => null]];

    self::assertSame($expected, $actual);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
