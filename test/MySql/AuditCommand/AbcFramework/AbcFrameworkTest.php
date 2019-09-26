<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\AbcFramework;

use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\Middle\Helper\RowSetHelper;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests for/with typical config for ABC Framework.
 */
class AbcFrameworkTest extends AuditCommandTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Connects to the MySQL server.
   */
  public static function setUpBeforeClass(): void
  {
    self::$dir = __DIR__;

    parent::setUpBeforeClass();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test audit table is created correctly.
   */
  public function test01(): void
  {
    $this->runAudit();

    // Reconnect to DB.
    StaticDataLayer::connect('localhost', 'test', 'test', self::$dataSchema);

    $sql = sprintf("
select COLUMN_NAME                    as column_name
,      COLUMN_TYPE                    as column_type
,      ifnull(COLUMN_DEFAULT, 'NULL') as column_default 
,      IS_NULLABLE                    as is_nullable
,      CHARACTER_SET_NAME             as character_set_name
,      COLLATION_NAME                 as collation_name
from   information_schema.COLUMNS
where  TABLE_SCHEMA = %s
and    TABLE_NAME   = %s
order by ORDINAL_POSITION",
                   StaticDataLayer::quoteString(self::$auditSchema),
                   StaticDataLayer::quoteString('ABC_AUTH_COMPANY'));

    $rows = StaticDataLayer::executeRows($sql);

    // MariaDB 10.2.x uses 'current_timestamp()' older versions use 'CURRENT_TIMESTAMP'.
    foreach ($rows as &$row)
    {
      if ($row['column_default']=='CURRENT_TIMESTAMP')
      {
        $row['column_default'] = 'current_timestamp()';
      }
    }

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
                 ['column_name'        => 'audit_uuid',
                  'column_type'        => 'bigint(20) unsigned',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'NO',
                  'character_set_name' => null,
                  'collation_name'     => null],
                 ['column_name'        => 'audit_rownum',
                  'column_type'        => 'int(10) unsigned',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'NO',
                  'character_set_name' => null,
                  'collation_name'     => null],
                 ['column_name'        => 'audit_ses_id',
                  'column_type'        => 'int(10) unsigned',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => null,
                  'collation_name'     => null],
                 ['column_name'        => 'audit_usr_id',
                  'column_type'        => 'int(10) unsigned',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => null,
                  'collation_name'     => null],
                 ['column_name'        => 'cmp_id',
                  'column_type'        => 'smallint(5) unsigned',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => null,
                  'collation_name'     => null],
                 ['column_name'        => 'cmp_abbr',
                  'column_type'        => 'varchar(15)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => 'utf8',
                  'collation_name'     => 'utf8_general_ci'],
                 ['column_name'        => 'cmp_label',
                  'column_type'        => 'varchar(20)',
                  'column_default'     => 'NULL',
                  'is_nullable'        => 'YES',
                  'character_set_name' => 'ascii',
                  'collation_name'     => 'ascii_general_ci']];

    self::assertEquals($expected, $rows);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test insert trigger is working correctly.
   */
  public function test02a(): void
  {
    // Insert a row into ABC_AUTH_COMPANY.
    $sql = sprintf('
insert into `ABC_AUTH_COMPANY`(`cmp_abbr`
,                              `cmp_label`)
values( %s
,       %s )',
                   StaticDataLayer::quoteString('SYS'),
                   StaticDataLayer::quoteString('SYS'));

    StaticDataLayer::executeNone($sql);

    // Get audit rows.
    $sql = sprintf("
select * 
from   `test_audit`.`ABC_AUTH_COMPANY`
where  `audit_statement` = 'INSERT'");

    StaticDataLayer::executeNone("SET time_zone = 'Europe/Amsterdam'");
    $rows = StaticDataLayer::executeRows($sql);

    // We expect 1 row.
    self::assertEquals(1, count($rows));
    $row = $rows[0];

    // Tests on fields.
    $time = new \DateTime();
    self::assertLessThanOrEqual(date_format($time->add(new \DateInterval('PT1M')), 'Y-m-d H:i:s'), $row['audit_timestamp']);
    $time = new \DateTime();
    self::assertGreaterThanOrEqual(date_format($time->sub(new \DateInterval('PT1M')), 'Y-m-d H:i:s'), $row['audit_timestamp']);
    self::assertSame('NEW', $row['audit_type']);
    self::assertNotEmpty($row['audit_uuid']);
    self::assertEquals(1, $row['audit_rownum']);
    self::assertNull($row['audit_ses_id']);
    self::assertNull($row['audit_usr_id']);
    self::assertSame(1, $row['cmp_id']);
    self::assertSame('SYS', $row['cmp_abbr']);
    self::assertSame('SYS', $row['cmp_label']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test update trigger is working correctly.
   */
  public function test02b(): void
  {
    // Set session and user ID.
    StaticDataLayer::executeNone('set @abc_g_ses_id=12345');  // The combination of my suitcase.
    StaticDataLayer::executeNone('set @abc_g_usr_id=7011');

    // Update a row into ABC_AUTH_COMPANY.
    $sql = sprintf('
update `ABC_AUTH_COMPANY`
set   `cmp_label` = %s
where `cmp_abbr` = %s',
                   StaticDataLayer::quoteString('CMP_ID_SYS'),
                   StaticDataLayer::quoteString('SYS'));

    StaticDataLayer::executeNone($sql);

    // Get audit rows.
    $sql = sprintf("
select * 
from   `test_audit`.`ABC_AUTH_COMPANY`
where  `audit_statement` = 'UPDATE'");

    StaticDataLayer::executeNone("SET time_zone = 'Europe/Amsterdam'");
    $rows = StaticDataLayer::executeRows($sql);

    // We expect 2 rows.
    self::assertEquals(2, count($rows), 'row count');

    // Tests on 'OLD' fields.
    $row  = $rows[RowSetHelper::searchInRowSet($rows, 'audit_type', 'OLD')];
    $time = new \DateTime();
    self::assertLessThanOrEqual(date_format($time->add(new \DateInterval('PT1M')), 'Y-m-d H:i:s'), $row['audit_timestamp']);
    $time = new \DateTime();
    self::assertGreaterThanOrEqual(date_format($time->sub(new \DateInterval('PT1M')), 'Y-m-d H:i:s'), $row['audit_timestamp']);
    self::assertSame('OLD', $row['audit_type']);
    self::assertNotEmpty($row['audit_uuid']);
    self::assertSame(2, $row['audit_rownum']);
    self::assertSame(12345, $row['audit_ses_id']);
    self::assertSame(7011, $row['audit_usr_id']);
    self::assertSame(1, $row['cmp_id']);
    self::assertSame('SYS', $row['cmp_abbr']);
    self::assertSame('SYS', $row['cmp_label']);

    // Tests on 'NEW' fields.
    $row  = $rows[RowSetHelper::searchInRowSet($rows, 'audit_type', 'NEW')];
    $time = new \DateTime();
    self::assertLessThanOrEqual(date_format($time->add(new \DateInterval('PT1M')), 'Y-m-d H:i:s'), $row['audit_timestamp']);
    $time = new \DateTime();
    self::assertGreaterThanOrEqual(date_format($time->sub(new \DateInterval('PT1M')), 'Y-m-d H:i:s'), $row['audit_timestamp']);
    self::assertSame('NEW', $row['audit_type']);
    self::assertNotEmpty($row['audit_uuid']);
    self::assertSame(2, $row['audit_rownum']);
    self::assertSame(12345, $row['audit_ses_id']);
    self::assertSame(7011, $row['audit_usr_id']);
    self::assertSame(1, $row['cmp_id']);
    self::assertSame('SYS', $row['cmp_abbr']);
    self::assertSame('CMP_ID_SYS', $row['cmp_label']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test delete trigger is working correctly.
   */
  public function test02c(): void
  {
    StaticDataLayer::executeNone("SET time_zone = 'Europe/Amsterdam'");

    // Delete a row from ABC_AUTH_COMPANY.
    $sql = sprintf('
delete from `ABC_AUTH_COMPANY`
where `cmp_abbr` = %s',
                   StaticDataLayer::quoteString('SYS'));

    StaticDataLayer::executeNone($sql);

    // Get audit rows.
    $sql = sprintf("
select * 
from   `test_audit`.`ABC_AUTH_COMPANY`
where  audit_statement = 'DELETE'");

    $rows = StaticDataLayer::executeRows($sql);

    // We expect 1 row.
    self::assertEquals(1, count($rows));
    $row = $rows[0];

    // Tests on fields.
    $time = new \DateTime();
    self::assertLessThanOrEqual(date_format($time->add(new \DateInterval('PT1M')), 'Y-m-d H:i:s'), $row['audit_timestamp']);
    $time = new \DateTime();
    self::assertGreaterThanOrEqual(date_format($time->sub(new \DateInterval('PT1M')), 'Y-m-d H:i:s'), $row['audit_timestamp']);
    self::assertSame('OLD', $row['audit_type']);
    self::assertNotEmpty($row['audit_uuid']);
    self::assertSame(3, $row['audit_rownum']);
    self::assertSame(12345, $row['audit_ses_id']);
    self::assertSame(7011, $row['audit_usr_id']);
    self::assertSame(1, $row['cmp_id']);
    self::assertSame('SYS', $row['cmp_abbr']);
    self::assertSame('CMP_ID_SYS', $row['cmp_label']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test total number of rows in audit table.
   */
  public function test02d(): void
  {
    // Get all audit rows.
    $sql = sprintf("
select * 
from   `test_audit`.`ABC_AUTH_COMPANY`");

    $rows = StaticDataLayer::executeRows($sql);

    // We expect 4 rows: 1 insert, 2 update, and 1 delete.
    self::assertEquals(4, count($rows));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Does not disconnect and connect to the database because we need continues numbering of audit_uuid and audit_rownum.
   */
  protected function setUp(): void
  {
    // Nothing to do.
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Does not disconnect and connect to the database because we need continues numbering of audit_uuid and audit_rownum.
   */
  protected function tearDown(): void
  {
    // Nothing to do.
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
