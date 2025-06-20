<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\PhpPlaisio;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\Middle\Helper\RowSetHelper;

/**
 * Tests for/with typical config for PhpPlaisio Framework.
 */
class PhpPlaisioTest extends AuditCommandTestCase
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
    AuditDataLayer::$dl->connect();

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
                   AuditDataLayer::$dl->quoteString(self::$auditSchema),
                   AuditDataLayer::$dl->quoteString('ABC_AUTH_COMPANY'));

    $actual = AuditDataLayer::$dl->executeRows($sql);

    $replace = ['bigint unsigned'   => 'bigint(20) unsigned',
                'int unsigned'      => 'int(10) unsigned',
                'smallint unsigned' => 'smallint(5) unsigned'];

    foreach ($actual as $key => $row)
    {
      // Fix for MySQL 5.x.
      $actual[$key]['column_default'] = str_replace('CURRENT_TIMESTAMP', 'current_timestamp()', $row['column_default']);

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

    self::assertEquals($expected, $actual);
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
                   AuditDataLayer::$dl->quoteString('SYS'),
                   AuditDataLayer::$dl->quoteString('SYS'));

    AuditDataLayer::$dl->executeNone($sql);

    // Get audit rows.
    $sql = "
select * 
from   `test_audit`.`ABC_AUTH_COMPANY`
where  `audit_statement` = 'INSERT'";

    AuditDataLayer::$dl->executeNone("SET time_zone = 'Europe/Amsterdam'");
    $rows = AuditDataLayer::$dl->executeRows($sql);

    // We expect 1 row.
    self::assertCount(1, $rows);
    $row = $rows[0];

    // Tests on fields.
    $time = new \DateTime('Europe/Amsterdam');
    self::assertLessThanOrEqual(date_format($time->add(new \DateInterval('PT1M')), 'Y-m-d H:i:s'), $row['audit_timestamp']);
    $time = new \DateTime('Europe/Amsterdam');
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
    AuditDataLayer::$dl->executeNone('set @audit_ses_id=12345');  // The combination of my suitcase.
    AuditDataLayer::$dl->executeNone('set @audit_usr_id=7011');

    // Update a row into ABC_AUTH_COMPANY.
    $sql = sprintf('
update `ABC_AUTH_COMPANY`
set   `cmp_label` = %s
where `cmp_abbr` = %s',
                   AuditDataLayer::$dl->quoteString('CMP_ID_SYS'),
                   AuditDataLayer::$dl->quoteString('SYS'));

    AuditDataLayer::$dl->executeNone($sql);

    // Get audit rows.
    $sql = "
select * 
from   `test_audit`.`ABC_AUTH_COMPANY`
where  `audit_statement` = 'UPDATE'";

    AuditDataLayer::$dl->executeNone("SET time_zone = 'Europe/Amsterdam'");
    $rows = AuditDataLayer::$dl->executeRows($sql);

    // We expect 2 rows.
    self::assertCount(2, $rows, 'row count');

    // Tests on 'OLD' fields.
    $row  = $rows[RowSetHelper::searchInRowSet($rows, 'audit_type', 'OLD')];
    $time = new \DateTime('Europe/Amsterdam');
    self::assertLessThanOrEqual(date_format($time->add(new \DateInterval('PT1M')), 'Y-m-d H:i:s'), $row['audit_timestamp']);
    $time = new \DateTime('Europe/Amsterdam');
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
    $time = new \DateTime('Europe/Amsterdam');
    self::assertLessThanOrEqual(date_format($time->add(new \DateInterval('PT1M')), 'Y-m-d H:i:s'), $row['audit_timestamp']);
    $time = new \DateTime('Europe/Amsterdam');
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
    AuditDataLayer::$dl->executeNone("SET time_zone = 'Europe/Amsterdam'");

    // Delete a row from ABC_AUTH_COMPANY.
    $sql = sprintf('
delete from `ABC_AUTH_COMPANY`
where `cmp_abbr` = %s',
                   AuditDataLayer::$dl->quoteString('SYS'));

    AuditDataLayer::$dl->executeNone($sql);

    // Get audit rows.
    $sql = "
select * 
from   `test_audit`.`ABC_AUTH_COMPANY`
where  audit_statement = 'DELETE'";

    $rows = AuditDataLayer::$dl->executeRows($sql);

    // We expect 1 row.
    self::assertCount(1, $rows);
    $row = $rows[0];

    // Tests on fields.
    $time = new \DateTime('Europe/Amsterdam');
    self::assertLessThanOrEqual(date_format($time->add(new \DateInterval('PT1M')), 'Y-m-d H:i:s'), $row['audit_timestamp']);
    $time = new \DateTime('Europe/Amsterdam');
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
    $sql = "
select * 
from   `test_audit`.`ABC_AUTH_COMPANY`";

    $rows = AuditDataLayer::$dl->executeRows($sql);

    // We expect 4 rows: 1 insert, 2 update, and 1 delete.
    self::assertCount(4, $rows);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Does not disconnect and connect to the database because we need continues numbering of audit_uuid and audit_rownum.
   */
  protected function setUp(): void
  {
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
