<?php
declare(strict_types=1);

namespace SetBased\Audit\Test\MySql\DiffCommand\NewColumnAuditTable;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\DiffCommand\DiffCommandTestCase;

/**
 * Tests new column in audit table.
 */
class NewColumnAuditTableTest extends DiffCommandTestCase
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
  /**
   * Runs the test.
   */
  public function test01(): void
  {
    // Run audit.
    $this->runAudit();

    // Create new column c3 in the audit table.
    AuditDataLayer::$dl->executeMulti(file_get_contents(__DIR__.'/config/create_new_column.sql'));

    $output = preg_replace('/ +/', ' ', $this->runDiff());

    // Fix for MySQL 8.x.
    $output = str_replace('mediumint ', 'mediumint(9) ', $output);

    self::assertStringContainsString('| c3 | mediumint(9) | |', $output, 'acquire');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
