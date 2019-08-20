<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\ObsoleteTable;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\Helper\RowSetHelper;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests for running audit with a new table.
 */
class ObsoleteTableTest extends AuditCommandTestCase
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
    // Preserve config file.
    copy(__DIR__.'/config/audit.json', __DIR__.'/config/audit.org.json');

    $this->runAudit();

    // TABLE1 and TABLE2 MUST exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE1'));
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE2'));

    // TABLE1 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_insert'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_update'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_delete'));

    // TABLE2 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE2');
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t2_insert'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t2_update'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t2_delete'));

    // Drop obsolete table TABLE2.
    StaticDataLayer::executeMulti(file_get_contents(__DIR__.'/config/drop_obsolete_table.sql'));

    $this->runAudit(0, true);

    // TABLE1 and TABLE2 MUST still exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE1'));
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE2'));

    // TABLE1 have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_insert'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_update'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_delete'));

    // TABLE2 MUST not be in audit.json.
    $config = file_get_contents(__DIR__.'/config/audit.json');
    self::assertStringContainsString('TABLE1', $config);
    self::assertStringNotContainsString('TABLE2', $config);

    // Restore config file.
    copy(__DIR__.'/config/audit.org.json', __DIR__.'/config/audit.json');
    unlink(__DIR__.'/config/audit.org.json');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
