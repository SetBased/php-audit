<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\ObsoleteTable;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\Middle\Helper\RowSetHelper;

/**
 * Tests for running audit with an obsolete table (i.e. table is not longer been audited).
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
    copy(__DIR__.'/config/audit.org.json', __DIR__.'/config/audit.json');

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

    // Set audit to false for TABLE2.
    $config = json_decode(file_get_contents(__DIR__.'/config/audit.json'), true);
    $config['tables']['TABLE2']['audit'] = false;
    file_put_contents(__DIR__.'/config/audit.json', json_encode($config, JSON_PRETTY_PRINT));

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

    // TABLE2 must not have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE2');
    self::assertCount(0, $triggers);

    // TABLE2 MUST be in audit.json.
    $config = file_get_contents(__DIR__.'/config/audit.json');
    self::assertStringContainsString('TABLE1', $config);
    self::assertStringContainsString('TABLE2', $config);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
