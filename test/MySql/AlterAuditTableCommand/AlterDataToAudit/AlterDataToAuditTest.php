<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\Test\MySql\AlterAuditTableCommand\AlterDataToAudit;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AlterAuditTableCommand\AlterAuditTableCommandTestCase;
use SetBased\Stratum\MySql\StaticDataLayer;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Tests for running audit with a new table column.
 */
class AlterDataToAuditTest extends AlterAuditTableCommandTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass()
  {
    self::$dir = __DIR__;

    parent::setUpBeforeClass();
  }

  //--------------------------------------------------------------------------------------------------------------------
  public function test01()
  {
    // Run audit.
    $this->runAudit();

    // TABLE1 MUST exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    $this->assertNotNull(StaticDataLayer::searchInRowSet('table_name', 'TABLE1', $tables));

    // Edit column type in data schema.
    StaticDataLayer::multiQuery(file_get_contents(__DIR__.'/config/edit_column_type.sql'));

    // TABLE1 must have column c4 - int(12).
    $actual = AuditDataLayer::getTableColumns(self::$auditSchema, 'TABLE1');

    $expected   = [];
    $expected[] = ['column_name'        => 'c1',
                   'column_type'        => 'tinyint(4)',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];
    $expected[] = ['column_name'        => 'c2',
                   'column_type'        => 'smallint(6)',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];
    $expected[] = ['column_name'        => 'c4',
                   'column_type'        => 'int(11)',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];

    $this->assertSame($expected, $actual);

    $this->runAlter();

    $this->assertSame(file_get_contents(__DIR__.'/config/alter-table-sql-result.sql'),
                      'ALTER TABLE test_audit.`TABLE1` CHANGE
  `c4` `c4` int(12)
;
');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
