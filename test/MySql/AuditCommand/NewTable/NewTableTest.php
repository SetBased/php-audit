<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\AuditCommand\NewTable;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\Helper\RowSetHelper;
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
  public static function setUpBeforeClass(): void
  {
    self::$dir = __DIR__;

    parent::setUpBeforeClass();
  }

  //--------------------------------------------------------------------------------------------------------------------
  public function test01(): void
  {
    copy(__DIR__.'/config/audit.org.json', __DIR__.'/config/audit.json');

    $this->runAudit(0, true);

    // TABLE1 MUST and TABLE2 MUST not exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE1'));
    self::assertNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE2'));

    // TABLE1 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_insert'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_update'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_delete'));

    // Create new table TABLE2.
    StaticDataLayer::executeMulti(file_get_contents(__DIR__.'/config/create_new_table.sql'));

    $output =  $this->runAudit(0, true);

    // New table must be logged.
    self::assertStringContainsString('Found new table TABLE2', $output);

    // TABLE2 MUST be in audit.json and audit must be null.
    $config = json_decode(file_get_contents(__DIR__.'/config/audit.json'), true);
    self::assertArrayHasKey('TABLE2', $config['tables']);
    self::assertIsArray($config['tables']['TABLE2']);
    self::assertNull($config['tables']['TABLE2']['audit']);

    // TABLE1 MUST and TABLE2 MUST not exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE1'));
    self::assertNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE2'));

    // TABLE1 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_insert'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_update'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_delete'));

    $output =  $this->runAudit(0, true);

    // New table must be logged.
    self::assertStringContainsString('Audit not set for table TABLE2', $output);

    // TABLE2 MUST be in audit.json and audit must be null.
    $config = json_decode(file_get_contents(__DIR__.'/config/audit.json'), true);
    self::assertArrayHasKey('TABLE2', $config['tables']);
    self::assertIsArray($config['tables']['TABLE2']);
    self::assertNull($config['tables']['TABLE2']['audit']);

    // TABLE1 MUST and TABLE2 MUST not exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE1'));
    self::assertNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE2'));

    // TABLE1 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_insert'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_update'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_delete'));

    // Set audit to true for TABLE2.
    $config = json_decode(file_get_contents(__DIR__.'/config/audit.json'), true);
    $config['tables']['TABLE2']['audit'] = true;
    $config['tables']['TABLE2']['alias'] = 't2';
    file_put_contents(__DIR__.'/config/audit.json', json_encode($config, JSON_PRETTY_PRINT));

    $output = $this->runAudit(0, true);

    // Creating of new audit table must be logged.
    self::assertStringContainsString('Creating audit table test_audit.TABLE2', $output);

    // TABLE1 and TABLE2 MUST exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE1'));
    self::assertNotNull(RowSetHelper::searchInRowSet($tables, 'table_name', 'TABLE2'));

    // TABLE1 and TABLE2 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_insert'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_update'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t1_delete'));
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE2');
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t2_insert'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t2_update'));
    self::assertNotNull(RowSetHelper::searchInRowSet($triggers, 'trigger_name', 'trg_audit_t2_delete'));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
