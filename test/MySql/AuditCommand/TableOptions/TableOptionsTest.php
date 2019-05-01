<?php
declare(strict_types=1);

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

    $dataTable  = AuditDataLayer::getTableOptions('test_data', 'TABLE1');
    $auditTable = AuditDataLayer::getTableOptions('test_audit', 'TABLE1');
    unset($dataTable['table_schema']);
    unset($auditTable['table_schema']);
    self::assertEquals($dataTable, $auditTable, 'TABLE1');

    $dataTable  = AuditDataLayer::getTableOptions('test_data', 'TABLE2');
    $auditTable = AuditDataLayer::getTableOptions('test_audit', 'TABLE2');
    unset($dataTable['table_schema']);
    unset($auditTable['table_schema']);
    self::assertEquals($dataTable, $auditTable, 'TABLE2');

    $dataTable  = AuditDataLayer::getTableOptions('test_data', 'TABLE3');
    $auditTable = AuditDataLayer::getTableOptions('test_audit', 'TABLE3');
    unset($dataTable['table_schema']);
    unset($auditTable['table_schema']);
    self::assertEquals($dataTable, $auditTable, 'TABLE3');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
