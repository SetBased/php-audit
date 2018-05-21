<?php

namespace SetBased\Audit\Test\MySql\AuditCommand\ObsoleteTable;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests for running audit with a new table.
 */
class ObsoleteTableTest extends AuditCommandTestCase
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
    // Preserve config file.
    copy(__DIR__.'/config/audit.json', __DIR__.'/config/audit.org.json');

    $this->runAudit();

    // TABLE1 and TABLE2 MUST exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(StaticDataLayer::searchInRowSet('table_name', 'TABLE1', $tables));
    self::assertNotNull(StaticDataLayer::searchInRowSet('table_name', 'TABLE2', $tables));

    // TABLE1 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_insert', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_update', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_delete', $triggers));

    // TABLE2 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE2');
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t2_insert', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t2_update', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t2_delete', $triggers));

    // Drop obsolete table TABLE2.
    StaticDataLayer::multiQuery(file_get_contents(__DIR__.'/config/drop_obsolete_table.sql'));

    $this->runAudit(0, true);

    // TABLE1 and TABLE2 MUST still exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(StaticDataLayer::searchInRowSet('table_name', 'TABLE1', $tables));
    self::assertNotNull(StaticDataLayer::searchInRowSet('table_name', 'TABLE2', $tables));

    // TABLE1 have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_insert', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_update', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_delete', $triggers));

    // TABLE2 MUST not be in audit.json.
    $config = file_get_contents(__DIR__.'/config/audit.json');
    self::assertContains('TABLE1', $config);
    self::assertNotContains('TABLE2', $config);

    // Restore config file.
    copy(__DIR__.'/config/audit.org.json', __DIR__.'/config/audit.json');
    unlink(__DIR__.'/config/audit.org.json');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
