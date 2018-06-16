<?php

namespace SetBased\Audit\Test\MySql\AuditCommand\NewTable;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests for running audit with a new table.
 */
class NewTableTest extends AuditCommandTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public static function setUpBeforeClass()
  {
    self::$dir = __DIR__;

    parent::setUpBeforeClass();
  }

  //--------------------------------------------------------------------------------------------------------------------
  public function test01()
  {
    $this->runAudit();

    // TABLE1 MUST and TABLE2 MUST not exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(StaticDataLayer::searchInRowSet('table_name', 'TABLE1', $tables));
    self::assertNull(StaticDataLayer::searchInRowSet('table_name', 'TABLE2', $tables));

    // TABLE1 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_insert', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_update', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_delete', $triggers));

    // Create new table TABLE2.
    StaticDataLayer::executeMulti(file_get_contents(__DIR__.'/config/create_new_table.sql'));

    $this->runAudit();

    // TABLE1 and TABLE2 MUST exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(StaticDataLayer::searchInRowSet('table_name', 'TABLE1', $tables));
    self::assertNotNull(StaticDataLayer::searchInRowSet('table_name', 'TABLE2', $tables));

    // TABLE1 and TABLE2 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_insert', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_update', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_delete', $triggers));
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE2');
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t2_insert', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t2_update', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t2_delete', $triggers));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
