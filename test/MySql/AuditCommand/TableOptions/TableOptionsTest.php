<?php

namespace SetBased\Audit\Test\MySql\AuditCommand\TableOptions;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;

/**
 * Tests for preservation of table options.
 */
class TableOptionsTest extends AuditCommandTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Connects to the MySQL server.
   */
  public static function setUpBeforeClass()
  {
    self::$dir = __DIR__;

    parent::setUpBeforeClass();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test audit table is created correctly.
   */
  public function test01()
  {
    $this->runAudit();

    $table1_data  = AuditDataLayer::getTableOptions('test_data', 'TABLE1');
    $table1_audit = AuditDataLayer::getTableOptions('test_audit', 'TABLE1');
    self::assertEquals($table1_data, $table1_audit, 'TABLE1');

    $table1_data  = AuditDataLayer::getTableOptions('test_data', 'TABLE2');
    $table1_audit = AuditDataLayer::getTableOptions('test_audit', 'TABLE2');
    self::assertEquals($table1_data, $table1_audit, 'TABLE2');

    $table1_data  = AuditDataLayer::getTableOptions('test_data', 'TABLE3');
    $table1_audit = AuditDataLayer::getTableOptions('test_audit', 'TABLE3');
    self::assertEquals($table1_data, $table1_audit, 'TABLE3');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
